'**********************************************************
'**  Video Player Example Application - DeviceInfo 
'**  November 2009
'**  Copyright (c) 2009 Roku Inc. All Rights Reserved.
'**********************************************************

'******************************************************
'Get our device version
'******************************************************

Function GetDeviceVersion()
    return CreateObject("roDeviceInfo").GetVersion()
End Function

'******************************************************
'Get our serial number
'******************************************************

Function GetDeviceESN()
    return CreateObject("roDeviceInfo").GetDeviceUniqueId()
End Function

'******************************************************
'List Registry entries
'   added by rshendershot
'******************************************************
Function showRegistrySection()
    Registry = CreateObject("roRegistry")
    i = 0
    for each section in Registry.GetSectionList()
        RegistrySection = CreateObject("roRegistrySection", section)
        for each key in RegistrySection.GetKeyList()
            i = i+1
            print i ":" section ":" key
        end for
    end for
    print i.toStr() " Registry Keys Listed"
End Function

sub DeleteRegistry()
    print "Starting Delete Registry"
    Registry = CreateObject("roRegistry")
    i = 0
    for each section in Registry.GetSectionList()
        RegistrySection = CreateObject("roRegistrySection", section)
        for each key in RegistrySection.GetKeyList()
            i = i+1
            print "Deleting " section + ":" key
            RegistrySection.Delete(key)
        end for
        RegistrySection.flush()
    end for
    print i.toStr() " Registry Keys Deleted"
end sub

